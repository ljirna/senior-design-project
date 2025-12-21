<?php
require_once __DIR__ . '/BaseDao.php';

class UserDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct("users");
    }

    // Get user by email
    public function getUserByEmail($email)
    {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get user by ID (without password)
    public function getUserById($user_id)
    {
        $stmt = $this->connection->prepare("
            SELECT user_id, full_name, email, phone_number, address, 
                   city, postal_code, role, created_at 
            FROM users 
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get all users with pagination
    public function getAllUsers($limit = 20, $offset = 0)
    {
        $stmt = $this->connection->prepare("
            SELECT user_id, full_name, email, phone_number, address, 
                   city, postal_code, role, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Count all users
    public function countAllUsers()
    {
        $stmt = $this->connection->prepare("SELECT COUNT(*) as total FROM users");
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    // Search users
    public function searchUsers($search_term, $limit = 20, $offset = 0)
    {
        $stmt = $this->connection->prepare("
            SELECT user_id, full_name, email, phone_number, address, 
                   city, postal_code, role, created_at 
            FROM users 
            WHERE full_name LIKE :search 
               OR email LIKE :search 
               OR phone_number LIKE :search 
            ORDER BY full_name 
            LIMIT :limit OFFSET :offset
        ");
        $search = "%" . $search_term . "%";
        $stmt->bindParam(':search', $search);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Update password
    public function updatePassword($user_id, $password_hash)
    {
        $stmt = $this->connection->prepare("
            UPDATE users 
            SET password_hash = :password_hash 
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    // Get user statistics
    public function getUserStatistics($user_id)
    {
        $stmt = $this->connection->prepare("
            SELECT 
                (SELECT COUNT(*) FROM orders WHERE user_id = :user_id) as total_orders,
                (SELECT COUNT(*) FROM reviews WHERE user_id = :user_id) as total_reviews,
                (SELECT COUNT(*) FROM favorites WHERE user_id = :user_id) as total_favorites,
                (SELECT SUM(total_amount) FROM orders WHERE user_id = :user_id AND status = 'completed') as total_spent
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Check if email exists
    public function emailExists($email, $exclude_user_id = null)
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        if ($exclude_user_id) {
            $sql .= " AND user_id != :exclude_user_id";
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':email', $email);
        if ($exclude_user_id) {
            $stmt->bindParam(':exclude_user_id', $exclude_user_id);
        }
        $stmt->execute();
        return $stmt->fetch()['count'] > 0;
    }
}
