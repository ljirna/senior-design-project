<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/CategoryDao.php';

class CategoryService extends BaseService
{
    public function __construct()
    {
        $dao = new CategoryDao();
        parent::__construct($dao);
    }
    
    public function getCategoryById($category_id) {
        return $this->dao->getCategoryById($category_id);
    }

    public function getAllCategoriesWithCount() {
        return $this->dao->getAllCategoriesWithCount();
    }

    public function getCategoryWithFees($category_id) {
        return $this->dao->getCategoryWithFees($category_id);
    }

    public function getCategoriesWithProducts($limit = 5) {
        return $this->dao->getCategoriesWithProducts($limit);
    }

    public function searchCategories($search_term) {
        return $this->dao->searchCategories($search_term);
    }

    public function delete($category_id) {
        // Check if category has products before deleting
        if ($this->dao->hasProducts($category_id)) {
            throw new Exception("Cannot delete category with existing products");
        }
        return $this->dao->delete($category_id);
    }

    // Business logic for fee calculation
    public function calculateCategoryFees($category_id, $quantity = 1) {
        $category = $this->dao->getCategoryWithFees($category_id);
        if (!$category) {
            throw new Exception("Category not found");
        }
        
        return [
            'delivery_fee' => $category['default_delivery_fee'] * $quantity,
            'assembly_fee' => $category['default_assembly_fee'] * $quantity,
            'per_item' => [
                'delivery' => $category['default_delivery_fee'],
                'assembly' => $category['default_assembly_fee']
            ]
        ];
    }

    // Validate category data
    public function validateCategoryData($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = "Category name is required";
        }
        
        if (isset($data['delivery_fee']) && $data['delivery_fee'] < 0) {
            $errors[] = "Delivery fee cannot be negative";
        }
        
        if (isset($data['assembly_fee']) && $data['assembly_fee'] < 0) {
            $errors[] = "Assembly fee cannot be negative";
        }
        
        if (empty($errors)) {
            return ['valid' => true];
        } else {
            return ['valid' => false, 'errors' => $errors];
        }
    }
}