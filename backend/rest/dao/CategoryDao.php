<?php
require_once __DIR__ . '/BaseDao.php';

class CategoryDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct("categories");
    }

    // Basic CRUD
    public function getCategoryById($category_id)
    {
        $stmt = $this->connection->prepare("SELECT * FROM categories WHERE category_id = :category_id");
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get all categories with product count
    public function getAllCategoriesWithCount()
    {
        $stmt = $this->connection->prepare("
            SELECT c.*, COUNT(p.product_id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON c.category_id = p.category_id 
            GROUP BY c.category_id 
            ORDER BY c.name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get category with fees
    public function getCategoryWithFees($category_id)
    {
        $stmt = $this->connection->prepare("
            SELECT *, 
                   delivery_fee as default_delivery_fee,
                   assembly_fee as default_assembly_fee
            FROM categories 
            WHERE category_id = :category_id
        ");
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get categories with products
    public function getCategoriesWithProducts($limit = 5)
    {
        $stmt = $this->connection->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count
            FROM categories c
            HAVING product_count > 0
            ORDER BY product_count DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Check if category has products
    public function hasProducts($category_id)
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count 
            FROM products 
            WHERE category_id = :category_id
        ");
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt->fetch()['count'] > 0;
    }

    // Search categories
    public function searchCategories($search_term)
    {
        $stmt = $this->connection->prepare("
            SELECT * FROM categories 
            WHERE name LIKE :search 
               OR description LIKE :search 
            ORDER BY name
        ");
        $search = "%" . $search_term . "%";
        $stmt->bindParam(':search', $search);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
