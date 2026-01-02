<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private function getUserFromRequest()
    {
        $user = Flight::get('user');
        if ($user) {
            return $user;
        }

        // Fallback: decode token from request header if not already set
        $token = Flight::request()->getHeader("Authentication");
        if (!$token) {
            return null;
        }

        $decoded_token = JWT::decode($token, new Key(Config::JWT_SECRET(), 'HS256'));
        $userPayload = $decoded_token->user ?? null;
        if (is_object($userPayload)) {
            $userPayload = (array)$userPayload;
        }
        if ($userPayload) {
            Flight::set('user', $userPayload);
            Flight::set('jwt_token', $token);
        }
        return $userPayload;
    }

    public function verifyToken($token)
    {
        if (!$token)
            Flight::halt(401, "Missing authentication header");
        $decoded_token = JWT::decode($token, new Key(Config::JWT_SECRET(), 'HS256'));
        // Normalize user payload to array for consistent downstream access
        $userPayload = $decoded_token->user;
        if (is_object($userPayload)) {
            $userPayload = (array)$userPayload;
        }
        Flight::set('user', $userPayload);
        Flight::set('jwt_token', $token);
        return TRUE;
    }
    public function authorizeRole($requiredRole)
    {
        $user = $this->getUserFromRequest();
        if (!$user) {
            Flight::halt(401, 'Unauthorized: user not resolved');
        }

        $role = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);
        if ($role !== $requiredRole) {
            Flight::halt(403, 'Access denied: insufficient privileges');
        }
    }
    public function authorizeRoles($roles)
    {
        $user = $this->getUserFromRequest();

        if (!$user) {
            Flight::halt(401, 'Unauthorized: user not resolved');
        }

        $role = null;
        if (is_array($user)) {
            $role = $user['role'] ?? $user['user_role'] ?? null;
        } else {
            $role = $user->role ?? $user->user_role ?? null;
        }

        if ($role !== null) {
            $role = strtolower($role);
        }

        if (!in_array($role, $roles)) {
            Flight::halt(403, 'Forbidden: role not allowed');
        }
    }
    function authorizePermission($permission)
    {
        $user = Flight::get('user');
        if (!in_array($permission, $user->permissions)) {
            Flight::halt(403, 'Access denied: permission missing');
        }
    }
}
