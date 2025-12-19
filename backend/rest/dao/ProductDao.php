<?php
require_once __DIR__ . '/BaseDao.php';

class ProductDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct("products");
    }

    // Basic CRUD
    public function getProductById($product_id) {
        $stmt = $this->connection->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            WHERE p.product_id = :product_id
        ");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // 1. Product Listing & Filtering
    public function getAllProducts($limit = 20, $offset = 0) {
        $stmt = $this->connection->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProductsByCategory($category_id, $limit = 20, $offset = 0) {
        $stmt = $this->connection->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            WHERE p.category_id = :category_id 
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 2. Search Functionality
    public function searchProducts($search_term, $limit = 20, $offset = 0) {
        $stmt = $this->connection->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            WHERE p.name LIKE :search 
               OR p.description LIKE :search 
               OR c.name LIKE :search 
            ORDER BY 
                CASE 
                    WHEN p.name LIKE :exact THEN 1
                    WHEN p.description LIKE :exact THEN 2
                    ELSE 3
                END
            LIMIT :limit OFFSET :offset
        ");
        $search = "%" . $search_term . "%";
        $exact = $search_term . "%";
        $stmt->bindParam(':search', $search);
        $stmt->bindParam(':exact', $exact);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 3. Price Filtering
    public function getProductsByPriceRange($min_price, $max_price, $category_id = null) {
        $sql = "
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            WHERE p.price BETWEEN :min_price AND :max_price
        ";
        
        if ($category_id) {
            $sql .= " AND p.category_id = :category_id";
        }
        
        $sql .= " ORDER BY p.price ASC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':min_price', $min_price);
        $stmt->bindParam(':max_price', $max_price);
        if ($category_id) {
            $stmt->bindParam(':category_id', $category_id);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 4. Featured & Popular Products
    public function getFeaturedProducts($limit = 8) {
        $stmt = $this->connection->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            ORDER BY RAND() 
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getNewArrivals($limit = 8) {
        $stmt = $this->connection->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 5. Related Products
    public function getRelatedProducts($product_id, $category_id, $limit = 4) {
        $stmt = $this->connection->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            WHERE p.product_id != :product_id 
              AND p.category_id = :category_id 
            ORDER BY RAND() 
            LIMIT :limit
        ");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 6. Pagination Counts
    public function countAllProducts() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) as total FROM products");
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    public function countProductsByCategory($category_id) {
        $stmt = $this->connection->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = :category_id");
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    public function countSearchResults($search_term) {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as total 
            FROM products p 
            JOIN categories c ON p.category_id = c.category_id 
            WHERE p.name LIKE :search 
               OR p.description LIKE :search 
               OR c.name LIKE :search
        ");
        $search = "%" . $search_term . "%";
        $stmt->bindParam(':search', $search);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    // 7. Product Images (if separate table)
    public function getProductImages($product_id) {
        $stmt = $this->connection->prepare("
            SELECT * FROM product_images 
            WHERE product_id = :product_id 
            ORDER BY is_primary DESC
        ");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 8. Product with Fees (from our previous discussion)
    public function getProductWithFees($product_id) {
        $stmt = $this->connection->prepare("
            SELECT 
                p.*,
                c.name as category_name,
                COALESCE(p.delivery_fee_override, c.delivery_fee) as delivery_fee,
                COALESCE(p.assembly_fee_override, c.assembly_fee) as assembly_fee,
                p.price + 
                    COALESCE(p.delivery_fee_override, c.delivery_fee) + 
                    COALESCE(p.assembly_fee_override, c.assembly_fee) as total_price
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.product_id = :product_id
        ");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // 9. Update Stock/Inventory
    public function updateStock($product_id, $quantity) {
        $stmt = $this->connection->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - :quantity 
            WHERE product_id = :product_id AND stock_quantity >= :quantity
        ");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}