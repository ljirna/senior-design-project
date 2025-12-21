<?php
require_once __DIR__ . '/BaseDao.php';

class FavoriteDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct("favorites");
    }

    // Get user's favorites
    public function getUserFavorites($user_id, $limit = 20, $offset = 0) {
        $stmt = $this->connection->prepare("
            SELECT f.*,
                   p.name as product_name,
                   p.description as product_description,
                   p.price,
                   p.delivery_fee_override,
                   p.assembly_fee_override,
                   c.name as category_name,
                   c.delivery_fee as category_delivery_fee,
                   c.assembly_fee as category_assembly_fee
            FROM favorites f
            JOIN products p ON f.product_id = p.product_id
            JOIN categories c ON p.category_id = c.category_id
            WHERE f.user_id = :user_id
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Check if product is favorited by user
    public function isProductFavorited($user_id, $product_id) {
        $stmt = $this->connection->prepare("
            SELECT * FROM favorites 
            WHERE user_id = :user_id AND product_id = :product_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Add product to favorites
    public function addToFavorites($user_id, $product_id) {
        // Check if already favorited
        if ($this->isProductFavorited($user_id, $product_id)) {
            throw new Exception("Product already in favorites");
        }

        $stmt = $this->connection->prepare("
            INSERT INTO favorites (user_id, product_id, created_at) 
            VALUES (:user_id, :product_id, NOW())
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }

    // Remove from favorites
    public function removeFromFavorites($user_id, $product_id) {
        $stmt = $this->connection->prepare("
            DELETE FROM favorites 
            WHERE user_id = :user_id AND product_id = :product_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }

    // Get favorite count for product
    public function getFavoriteCount($product_id) {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count 
            FROM favorites 
            WHERE product_id = :product_id
        ");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        return $stmt->fetch()['count'];
    }

    // Get user's favorite count
    public function getUserFavoriteCount($user_id) {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count 
            FROM favorites 
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch()['count'];
    }

    // Get popular favorites (most favorited products)
    public function getPopularFavorites($limit = 10) {
        $stmt = $this->connection->prepare("
            SELECT p.*,
                   COUNT(f.favorite_id) as favorite_count,
                   c.name as category_name
            FROM favorites f
            JOIN products p ON f.product_id = p.product_id
            JOIN categories c ON p.category_id = c.category_id
            GROUP BY f.product_id
            ORDER BY favorite_count DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}