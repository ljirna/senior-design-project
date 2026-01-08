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

    public function getProductById($product_id)
    {
        $product = $this->dao->getProductById($product_id);
        if ($product) {
            $product['images'] = $this->dao->getProductImages($product_id);
        }
        return $product;
    }

    // Override update to use product_id as the ID column and handle image_url
    public function update($product_id, $data)
    {
        $imageUrl = $data['image_url'] ?? null;

        // Remove image_url from product data since it goes to product_images table
        unset($data['image_url']);

        // Update the product
        $result = $this->dao->update($data, $product_id, 'product_id');

        // If image_url is provided, update the product image
        if ($imageUrl) {
            // Get existing images
            $images = $this->dao->getProductImages($product_id);

            if (!empty($images)) {
                // Update the first (primary) image
                $this->dao->updateProductImage($images[0]['image_id'], $imageUrl);
            } else {
                // Insert new image
                $this->dao->insertProductImage($product_id, $imageUrl, 1);
            }
        }

        return $result;
    }

    // Override create to handle image_url if provided
    public function create($data)
    {
        $imageUrl = $data['image_url'] ?? null;

        // Remove image_url from product data since it goes to product_images table
        unset($data['image_url']);

        // Create the product
        $result = $this->dao->add($data);

        if (!$result) {
            return false;
        }

        // If image_url is provided, insert it into product_images table
        if ($imageUrl && isset($result['product_id'])) {
            $this->dao->insertProductImage($result['product_id'], $imageUrl, 1);
        }

        return $result;
    }

    public function getAllProducts($limit = 20, $offset = 0)
    {
        $products = $this->dao->getAllProducts($limit, $offset);
        if ($products) {
            foreach ($products as &$product) {
                $product['images'] = $this->dao->getProductImages($product['product_id']);
            }
        }
        return $products;
    }

    public function getProductsByCategory($category_id, $limit = 20, $offset = 0)
    {
        $products = $this->dao->getProductsByCategory($category_id, $limit, $offset);
        if ($products) {
            foreach ($products as &$product) {
                $product['images'] = $this->dao->getProductImages($product['product_id']);
            }
        }
        return $products;
    }

    public function searchProducts($search_term, $limit = 20, $offset = 0)
    {
        $products = $this->dao->searchProducts($search_term, $limit, $offset);
        if ($products) {
            foreach ($products as &$product) {
                $product['images'] = $this->dao->getProductImages($product['product_id']);
            }
        }
        return $products;
    }

    public function getFeaturedProducts($limit = 8)
    {
        $products = $this->dao->getFeaturedProducts($limit);
        if ($products) {
            foreach ($products as &$product) {
                $product['images'] = $this->dao->getProductImages($product['product_id']);
            }
        }
        return $products;
    }

    public function getNewArrivals($limit = 8)
    {
        $products = $this->dao->getNewArrivals($limit);
        if ($products) {
            foreach ($products as &$product) {
                $product['images'] = $this->dao->getProductImages($product['product_id']);
            }
        }
        return $products;
    }

    public function getRelatedProducts($product_id, $category_id, $limit = 4)
    {
        $products = $this->dao->getRelatedProducts($product_id, $category_id, $limit);
        if ($products) {
            foreach ($products as &$product) {
                $product['images'] = $this->dao->getProductImages($product['product_id']);
            }
        }
        return $products;
    }

    public function getProductWithFees($product_id)
    {
        return $this->dao->getProductWithFees($product_id);
    }

    public function getProductsByPriceRange($min_price, $max_price, $category_id = null)
    {
        return $this->dao->getProductsByPriceRange($min_price, $max_price, $category_id);
    }

    // Business logic for stock validation
    public function validateStock($product_id, $requested_quantity)
    {
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
    public function calculateTotalPrice($product_id, $quantity = 1)
    {
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

    // Add new image to product
    public function addProductImage($product_id, $image_url)
    {
        // Verify product exists
        $product = $this->dao->getProductById($product_id);
        if (!$product) {
            throw new Exception('Product not found');
        }

        // Get existing images to determine if this should be primary
        $images = $this->dao->getProductImages($product_id);
        $is_primary = empty($images) ? 1 : 0; // Only primary if no images exist

        return $this->dao->insertProductImage($product_id, $image_url, $is_primary);
    }

    // Delete product image
    public function deleteProductImage($image_id, $product_id)
    {
        // Verify image belongs to the product
        $image = $this->dao->getProductImage($image_id);
        if (!$image || $image['product_id'] != $product_id) {
            return false;
        }

        // Get remaining images for the product
        $images = $this->dao->getProductImages($product_id);

        // If we're deleting the primary image and there are other images, 
        // make the first remaining image primary
        if ($image['is_primary'] && count($images) > 1) {
            foreach ($images as $img) {
                if ($img['image_id'] != $image_id) {
                    $this->dao->updateProductImagePrimary($img['image_id'], 1);
                    break;
                }
            }
        }

        return $this->dao->deleteProductImage($image_id);
    }
}
