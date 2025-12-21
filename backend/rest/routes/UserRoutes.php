<?php
require_once __DIR__ . '/../services/UserService.php';

// User registration
Flight::route('POST /users/register', function () {
    try {
        $data = Flight::request()->data->getData();
        Flight::userService()->register($data);
        Flight::json(['message' => 'User registered successfully'], 201);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// User login
Flight::route('POST /users/login', function () {
    try {
        $data = Flight::request()->data->getData();
        $user = Flight::userService()->login($data['email'], $data['password']);
        
        // You might want to generate JWT token here
        // $token = generateJWT($user);
        
        Flight::json([
            'message' => 'Login successful',
            'user' => $user,
            // 'token' => $token
        ]);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 401);
    }
});

// Get user profile
Flight::route('GET /users/@user_id', function ($user_id) {
    Flight::json(Flight::userService()->getUserById($user_id));
});

// Get user statistics
Flight::route('GET /users/@user_id/statistics', function ($user_id) {
    Flight::json(Flight::userService()->getUserStatistics($user_id));
});

// Update user profile
Flight::route('PUT /users/@user_id', function ($user_id) {
    try {
        $data = Flight::request()->data->getData();
        Flight::userService()->update($user_id, $data);
        Flight::json(['message' => 'Profile updated successfully']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Change password
Flight::route('PUT /users/@user_id/change-password', function ($user_id) {
    try {
        $data = Flight::request()->data->getData();
        Flight::userService()->changePassword($user_id, $data['current_password'], $data['new_password']);
        Flight::json(['message' => 'Password changed successfully']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Get all users (Admin only)
Flight::route('GET /users', function () {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::userService()->getAllUsers($limit, $offset));
});

// Search users (Admin only)
Flight::route('GET /users/search/@search_term', function ($search_term) {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::userService()->searchUsers($search_term, $limit, $offset));
});

// Delete user (Admin only)
Flight::route('DELETE /users/@user_id', function ($user_id) {
    try {
        Flight::userService()->delete($user_id);
        Flight::json(['message' => 'User deleted successfully']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});