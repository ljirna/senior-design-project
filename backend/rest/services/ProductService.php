<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/ProductDao.php';

class ProductService extends BaseService
{
    public function __construct()
    {
        $dao = new ProductDao();
        parent::__construct($dao);
    }
    
    public function getProductById($product_id) {
        $product = $this->dao->getProductById($product_id);
        if ($product) {
            $product['images'] = $this->dao->getProductImages($product_id);
        }
        return $product;
    }

    public function getAllProducts($limit = 20, $offset = 0) {
        return $this->dao->getAllProducts($limit, $offset);
    }

    public function getProductsByCategory($category_id, $limit = 20, $offset = 0) {
        return $this->dao->getProductsByCategory($category_id, $limit, $offset);
    }

    public function searchProducts($search_term, $limit = 20, $offset = 0) {
        return $this->dao->searchProducts($search_term, $limit, $offset);
    }

    public function getFeaturedProducts($limit = 8) {
        return $this->dao->getFeaturedProducts($limit);
    }

    public function getNewArrivals($limit = 8) {
        return $this->dao->getNewArrivals($limit);
    }

    public function getRelatedProducts($product_id, $category_id, $limit = 4) {
        return $this->dao->getRelatedProducts($product_id, $category_id, $limit);
    }

    public function getProductWithFees($product_id) {
        return $this->dao->getProductWithFees($product_id);
    }

    public function getProductsByPriceRange($min_price, $max_price, $category_id = null) {
        return $this->dao->getProductsByPriceRange($min_price, $max_price, $category_id);
    }

    // Business logic for stock validation
    public function validateStock($product_id, $requested_quantity) {
        $product = $this->dao->getProductById($product_id);
        if (!$product) {
            return ['valid' => false, 'message' => 'Product not found'];
        }
        
        if ($product['stock_quantity'] < $requested_quantity) {
            return [
                'valid' => false, 
                'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' items available'
            ];
        }
        
        return ['valid' => true, 'product' => $product];
    }

    // Calculate total with fees
    public function calculateTotalPrice($product_id, $quantity = 1) {
        $product = $this->dao->getProductWithFees($product_id);
        if (!$product) {
            return null;
        }
        
        $subtotal = $product['price'] * $quantity;
        $delivery = $product['delivery_fee'];
        $assembly = $product['assembly_fee'];
        
        return [
            'subtotal' => $subtotal,
            'delivery_fee' => $delivery,
            'assembly_fee' => $assembly,
            'total' => $subtotal + $delivery + $assembly,
            'per_item' => $product['price']
        ];
    }
}