<?php
require_once __DIR__ . '/../services/UserService.php';

// Helper to normalize request body into an associative array
function parse_request_body()
{
    // Try raw JSON body first
    $raw = Flight::request()->getBody();
    if (!$raw) {
        $raw = file_get_contents('php://input');
    }

    if ($raw) {
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }
    }

    // Fallback to Flight parsed data
    $data = Flight::request()->data->getData();
    if (is_object($data)) {
        $data = (array)$data;
    }

    if (is_string($data)) {
        // Attempt to parse querystring style payloads
        $tmp = [];
        parse_str($data, $tmp);
        if (!empty($tmp)) {
            return $tmp;
        }
        // As a last resort, return empty array to avoid foreach on string
        return [];
    }

    return is_array($data) ? $data : [];
}

Flight::group('/users', function () {
    // Get current user profile - BOTH admin and customer
    Flight::route('GET /profile/me', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $user_id = is_array($user) ? ($user['id'] ?? $user['user_id'] ?? null) : ($user->id ?? $user->user_id ?? null);

        if (!$user_id) {
            Flight::json(['error' => 'Invalid user payload'], 500);
            return;
        }

        $user_data = Flight::userService()->getUserById($user_id);

        if (!$user_data) {
            Flight::json(['error' => 'User not found'], 404);
            return;
        }

        // Remove sensitive data
        unset($user_data['password_hash']);

        Flight::json($user_data);
    });

    // Get user by ID - ADMIN or own profile
    Flight::route('GET /@user_id', function ($user_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $current_user = Flight::get('user');
        $current_user_role = is_array($current_user) ? ($current_user['role'] ?? null) : ($current_user->role ?? null);
        $current_user_id = is_array($current_user) ? ($current_user['id'] ?? $current_user['user_id'] ?? null) : ($current_user->id ?? $current_user->user_id ?? null);

        // Check if user has access
        if ($current_user_role !== Roles::ADMIN && $current_user_id != $user_id) {
            Flight::json(['error' => 'Access denied'], 403);
            return;
        }

        $user = Flight::userService()->getUserById($user_id);
        if (!$user) {
            Flight::json(['error' => 'User not found'], 404);
            return;
        }

        // Remove sensitive data
        unset($user['password_hash']);

        Flight::json($user);
    });

    // Get user statistics - ADMIN or own statistics
    Flight::route('GET /@user_id/statistics', function ($user_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $current_user = Flight::get('user');
        $current_user_role = is_array($current_user) ? ($current_user['role'] ?? null) : ($current_user->role ?? null);
        $current_user_id = is_array($current_user) ? ($current_user['id'] ?? $current_user['user_id'] ?? null) : ($current_user->id ?? $current_user->user_id ?? null);

        // Check if user has access
        if ($current_user_role !== Roles::ADMIN && $current_user_id != $user_id) {
            Flight::json(['error' => 'Access denied'], 403);
            return;
        }

        $statistics = Flight::userService()->getUserStatistics($user_id);
        Flight::json($statistics);
    });

    // Update user profile - ADMIN or own profile
    Flight::route('PUT /@user_id', function ($user_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $current_user = Flight::get('user');
        $current_user_role = is_array($current_user) ? ($current_user['role'] ?? null) : ($current_user->role ?? null);
        $current_user_id = is_array($current_user) ? ($current_user['id'] ?? $current_user['user_id'] ?? null) : ($current_user->id ?? $current_user->user_id ?? null);
        $data = parse_request_body();

        // Check if user has access
        if ($current_user_role !== Roles::ADMIN && $current_user_id != $user_id) {
            Flight::json(['error' => 'Access denied'], 403);
            return;
        }

        // Remove fields that shouldn't be updated through this endpoint
        unset($data['role']);
        unset($data['password']);
        unset($data['password_hash']);

        try {
            $user = Flight::userService()->update($user_id, $data);
            unset($user['password_hash']);
            Flight::json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Change password - ADMIN or own profile
    Flight::route('POST /@user_id/change-password', function ($user_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $current_user = Flight::get('user');
        $current_user_role = is_array($current_user) ? ($current_user['role'] ?? null) : ($current_user->role ?? null);
        $current_user_id = is_array($current_user) ? ($current_user['id'] ?? $current_user['user_id'] ?? null) : ($current_user->id ?? $current_user->user_id ?? null);
        $data = parse_request_body();

        // Check if user has access
        if ($current_user_role !== Roles::ADMIN && $current_user_id != $user_id) {
            Flight::json(['error' => 'Access denied'], 403);
            return;
        }

        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            Flight::json(['error' => 'Current password and new password are required'], 400);
            return;
        }

        try {
            $result = Flight::userService()->changePassword($user_id, $data['current_password'], $data['new_password']);
            Flight::json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // --- ADMIN ONLY ROUTES BELOW ---

    // Get all users - ADMIN ONLY
    Flight::route('GET /', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        $users = Flight::userService()->getAllUsers($limit, $offset);

        // Remove sensitive data before sending
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }

        Flight::json($users);
    });

    // Create user - ADMIN ONLY (customers register via /auth/register)
    Flight::route('POST /', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        // Validate required fields
        if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
            Flight::json(['error' => 'Full name, email and password are required'], 400);
            return;
        }

        // Check if email already exists
        $existing_user = Flight::userService()->getUserByEmail($data['email']);
        if ($existing_user) {
            Flight::json(['error' => 'Email already registered'], 400);
            return;
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);

        // Set default role if not specified
        if (!isset($data['role'])) {
            $data['role'] = Roles::CUSTOMER;
        }

        try {
            $user = Flight::userService()->add($data);
            unset($user['password_hash']);
            Flight::json($user, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Update user role - ADMIN ONLY
    Flight::route('PUT /@user_id/role', function ($user_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        if (!isset($data['role'])) {
            Flight::json(['error' => 'Role is required'], 400);
            return;
        }

        // Validate role
        if (!in_array($data['role'], [Roles::ADMIN, Roles::CUSTOMER])) {
            Flight::json(['error' => 'Invalid role'], 400);
            return;
        }

        // Prevent admin from removing their own admin role
        $current_user = Flight::get('user');
        if ($current_user['id'] == $user_id && $data['role'] !== Roles::ADMIN) {
            Flight::json(['error' => 'Cannot remove your own admin role'], 400);
            return;
        }

        try {
            $user = Flight::userService()->update($user_id, ['role' => $data['role']]);
            unset($user['password_hash']);
            Flight::json([
                'success' => true,
                'message' => 'User role updated',
                'data' => $user
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Search users - ADMIN ONLY
    Flight::route('GET /search', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $search_term = Flight::request()->query['q'] ?? '';
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        $users = Flight::userService()->searchUsers($search_term, $limit, $offset);

        // Remove sensitive data
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }

        Flight::json($users);
    });

    // Delete user - ADMIN ONLY
    Flight::route('DELETE /@user_id', function ($user_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $current_user = Flight::get('user');

        // Prevent admin from deleting themselves
        if ($current_user['id'] == $user_id) {
            Flight::json(['error' => 'Cannot delete your own account'], 400);
            return;
        }

        try {
            $result = Flight::userService()->delete($user_id);
            Flight::json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });
});
