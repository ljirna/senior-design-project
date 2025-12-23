<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/AuthDao.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService extends BaseService
{
    private $auth_dao;

    public function __construct()
    {
        $this->auth_dao = new AuthDao();
        parent::__construct($this->auth_dao);
    }

    public function get_user_by_email($email)
    {
        return $this->auth_dao->get_user_by_email($email);
    }

    public function register($entity)
    {
        if (empty($entity['email']) || empty($entity['password'])) {
            return ['success' => false, 'error' => 'Email and password are required.'];
        }

        $email_exists = $this->auth_dao->get_user_by_email($entity['email']);
        if ($email_exists) {
            return ['success' => false, 'error' => 'Email already registered.'];
        }

        // Map 'password' to 'password_hash' column
        $entity['password_hash'] = password_hash($entity['password'], PASSWORD_BCRYPT);
        unset($entity['password']); // Remove the plain password key

        // Set default role if not provided
        if (!isset($entity['role'])) {
            $entity['role'] = 'customer'; // or whatever default role you want
        }

        // create is method from parent (BaseService) where this entity will be written into users table
        $result = parent::create($entity);

        // Don't return password_hash in response
        unset($result['password_hash']);

        return ['success' => true, 'data' => $result];
    }

    public function login($entity)
    {
        if (empty($entity['email']) || empty($entity['password'])) {
            return ['success' => false, 'error' => 'Email and password are required.'];
        }

        $user = $this->auth_dao->get_user_by_email($entity['email']);
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email.'];
        }

        // Check password against password_hash column
        if (!password_verify($entity['password'], $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid password.'];
        }

        // Remove sensitive data
        unset($user['password_hash']);

        $jwt_payload = [
            'user' => $user,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // valid for day
        ];

        $token = JWT::encode(
            $jwt_payload,
            Config::JWT_SECRET(),
            'HS256'
        );

        return ['success' => true, 'data' => array_merge($user, ['token' => $token])];
    }
}
