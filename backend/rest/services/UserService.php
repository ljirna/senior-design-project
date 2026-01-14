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

    public function getCustomers($limit = 20, $offset = 0)
    {
        return $this->dao->getCustomers($limit, $offset);
    }

    public function searchUsers($search_term, $limit = 20, $offset = 0)
    {
        return $this->dao->searchUsers($search_term, $limit, $offset);
    }

    public function getUserStatistics($user_id)
    {
        return $this->dao->getUserStatistics($user_id);
    }


    public function update($user_id, $data)
    {
        $validation = $this->validateUserData($data, false);
        if (!$validation['valid']) {
            throw new Exception(implode(", ", $validation['errors']));
        }

        if (isset($data['email']) && $this->dao->emailExists($data['email'], $user_id)) {
            throw new Exception("Email already in use");
        }

        unset($data['role']);
        unset($data['password_hash']);

        return $this->dao->update($data, $user_id, 'user_id');
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

        if (isset($data['phone_number']) && $data['phone_number'] !== "") {
            if (!preg_match('/^[0-9+\-\s()]{6,20}$/', $data['phone_number'])) {
                $errors[] = "Invalid phone number format";
            }
        }

        if (empty($errors)) {
            return ['valid' => true];
        } else {
            return ['valid' => false, 'errors' => $errors];
        }
    }
}
