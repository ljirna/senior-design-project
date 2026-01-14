<?php
require_once __DIR__ . '/BaseDao.php';

class CategoryDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct("categories");
    }


    public function getCategoryById($category_id)
    {
        $stmt = $this->connection->prepare("SELECT * FROM categories WHERE category_id = :category_id");
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        return $stmt->fetch();
    }

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

    public function add($entity)
    {
        $query = "INSERT INTO categories (";
        foreach ($entity as $column => $value) {
            $query .= $column . ', ';
        }
        $query = substr($query, 0, -2);
        $query .= ") VALUES (";
        foreach ($entity as $column => $value) {
            $query .= ":" . $column . ', ';
        }
        $query = substr($query, 0, -2);
        $query .= ")";

        $stmt = $this->connection->prepare($query);
        $stmt->execute($entity);
        $entity['category_id'] = $this->connection->lastInsertId();
        return $entity;
    }

    public function update($entity, $id, $id_column = "category_id")
    {
        $query = "UPDATE categories SET ";
        foreach ($entity as $column => $value) {
            if ($column !== 'category_id') {
                $query .= $column . "=:" . $column . ", ";
            }
        }
        $query = substr($query, 0, -2);
        $query .= " WHERE category_id = :id";
        $stmt = $this->connection->prepare($query);
        $entity['id'] = $id;
        $stmt->execute($entity);
        return $entity;
    }

    public function delete($category_id)
    {
        $stmt = $this->connection->prepare("DELETE FROM categories WHERE category_id = :id");
        $stmt->bindValue(':id', $category_id);
        $stmt->execute();
    }
}
