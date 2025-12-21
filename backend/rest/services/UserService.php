<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/UserDao.php';

class UserService extends BaseService
{
    public function __construct()
    {
        $dao = new UserDao();
        parent::__construct($dao);
    }

    public function getUserById($user_id)
    {
        return $this->dao->getUserById($user_id);
    }

    public function getUserByEmail($email)
    {
        return $this->dao->getUserByEmail($email);
    }

    public function getAllUsers($limit = 20, $offset = 0)
    {
        return $this->dao->getAllUsers($limit, $offset);
    }

    public function searchUsers($search_term, $limit = 20, $offset = 0)
    {
        return $this->dao->searchUsers($search_term, $limit, $offset);
    }

    public function getUserStatistics($user_id)
    {
        return $this->dao->getUserStatistics($user_id);
    }

    public function register($data)
    {
        // Validate input
        $validation = $this->validateUserData($data, true);
        if (!$validation['valid']) {
            throw new Exception(implode(", ", $validation['errors']));
        }

        // Check if email already exists
        if ($this->dao->emailExists($data['email'])) {
            throw new Exception("Email already registered");
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);

        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'customer';
        }

        // Remove confirm_password if exists
        unset($data['confirm_password']);

        return $this->dao->insert($data);
    }

    public function login($email, $password)
    {
        $user = $this->dao->getUserByEmail($email);

        if (!$user) {
            throw new Exception("Invalid email or password");
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Invalid email or password");
        }

        // Remove password hash from returned data
        unset($user['password_hash']);
        return $user;
    }

    public function update($user_id, $data)
    {
        // Validate input
        $validation = $this->validateUserData($data, false);
        if (!$validation['valid']) {
            throw new Exception(implode(", ", $validation['errors']));
        }

        // Check if email is being changed and already exists
        if (isset($data['email']) && $this->dao->emailExists($data['email'], $user_id)) {
            throw new Exception("Email already in use");
        }

        // Don't allow role change through regular update
        unset($data['role']);
        unset($data['password_hash']);

        return $this->dao->update($user_id, $data);
    }

    public function changePassword($user_id, $current_password, $new_password)
    {
        $user = $this->dao->getUserByEmail($this->getUserById($user_id)['email']);

        if (!password_verify($current_password, $user['password_hash'])) {
            throw new Exception("Current password is incorrect");
        }

        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        return $this->dao->updatePassword($user_id, $new_password_hash);
    }

    public function delete($user_id)
    {
        // Optional: Check if user has orders before deleting
        // Or implement soft delete
        return $this->dao->delete($user_id);
    }

    private function validateUserData($data, $isRegistration = false)
    {
        $errors = [];

        if ($isRegistration) {
            if (empty($data['full_name'])) {
                $errors[] = "Full name is required";
            }

            if (empty($data['email'])) {
                $errors[] = "Email is required";
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }

            if (empty($data['password'])) {
                $errors[] = "Password is required";
            } elseif (strlen($data['password']) < 6) {
                $errors[] = "Password must be at least 6 characters";
            }

            if (isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
                $errors[] = "Passwords do not match";
            }
        }

        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (isset($data['phone_number']) && !preg_match('/^[0-9+\-\s()]{10,}$/', $data['phone_number'])) {
            $errors[] = "Invalid phone number format";
        }

        if (empty($errors)) {
            return ['valid' => true];
        } else {
            return ['valid' => false, 'errors' => $errors];
        }
    }
}
