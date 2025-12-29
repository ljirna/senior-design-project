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

        // Check if email already exists
        if ($this->auth_dao->get_user_by_email($entity['email'])) {
            return ['success' => false, 'error' => 'Email already registered.'];
        }

        // Build ONLY fields that exist in DB
        $new_user = [
            'email' => $entity['email'],
            'password_hash' => password_hash($entity['password'], PASSWORD_BCRYPT),
            'role' => 'customer'
        ];

        // Optional fields (only if sent)
        if (!empty($entity['fullName'])) {
            $new_user['full_name'] = $entity['fullName'];
        }

        if (!empty($entity['phone_number'])) {
            $new_user['phone_number'] = $entity['phone_number'];
        }

        if (!empty($entity['address'])) {
            $new_user['address'] = $entity['address'];
        }

        if (!empty($entity['city'])) {
            $new_user['city'] = $entity['city'];
        }

        if (!empty($entity['postal_code'])) {
            $new_user['postal_code'] = $entity['postal_code'];
        }

        // Insert into DB
        $result = parent::create($new_user);

        // Never return password hash
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
