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

    public function getCategoryById($category_id)
    {
        return $this->dao->getCategoryById($category_id);
    }

    public function getAllCategoriesWithCount()
    {
        return $this->dao->getAllCategoriesWithCount();
    }

    public function add($data)
    {
        return $this->dao->add($data);
    }

    public function update($category_id, $data)
    {
        return $this->dao->update($data, $category_id);
    }

    public function getCategoryWithFees($category_id)
    {
        return $this->dao->getCategoryWithFees($category_id);
    }

    public function getCategoriesWithProducts($limit = 5)
    {
        return $this->dao->getCategoriesWithProducts($limit);
    }

    public function searchCategories($search_term)
    {
        return $this->dao->searchCategories($search_term);
    }

    public function delete($category_id)
    {
        if ($this->dao->hasProducts($category_id)) {
            throw new Exception("Cannot delete category with existing products");
        }
        return $this->dao->delete($category_id);
    }

    public function validateCategoryData($data)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = "Category name is required";
        }

        if (empty($errors)) {
            return ['valid' => true];
        } else {
            return ['valid' => false, 'errors' => $errors];
        }
    }
}
