<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/FavoriteDao.php';
require_once __DIR__ . '/../dao/ProductDao.php';

class FavoriteService extends BaseService
{
    private $productDao;

    public function __construct()
    {
        $dao = new FavoriteDao();
        parent::__construct($dao);
        $this->productDao = new ProductDao();
    }

    public function getUserFavorites($user_id, $limit = 20, $offset = 0)
    {
        return $this->dao->getUserFavorites($user_id, $limit, $offset);
    }

    public function isProductFavorited($user_id, $product_id)
    {
        return $this->dao->isProductFavorited($user_id, $product_id) ? true : false;
    }

    public function addToFavorites($user_id, $product_id)
    {
        // Validate product exists
        $product = $this->productDao->getProductById($product_id);
        if (!$product) {
            throw new Exception("Product not found");
        }

        return $this->dao->addToFavorites($user_id, $product_id);
    }

    public function removeFromFavorites($user_id, $product_id)
    {
        return $this->dao->removeFromFavorites($user_id, $product_id);
    }

    public function getFavoriteCount($product_id)
    {
        return $this->dao->getFavoriteCount($product_id);
    }

    public function getUserFavoriteCount($user_id)
    {
        return $this->dao->getUserFavoriteCount($user_id);
    }

    public function getPopularFavorites($limit = 10)
    {
        return $this->dao->getPopularFavorites($limit);
    }

    public function toggleFavorite($user_id, $product_id)
    {
        // Validate product exists
        $product = $this->productDao->getProductById($product_id);
        if (!$product) {
            throw new Exception("Product not found");
        }

        $isFavorited = $this->dao->isProductFavorited($user_id, $product_id);

        if ($isFavorited) {
            $this->dao->removeFromFavorites($user_id, $product_id);
            return ['action' => 'removed', 'is_favorited' => false];
        } else {
            $this->dao->addToFavorites($user_id, $product_id);
            return ['action' => 'added', 'is_favorited' => true];
        }
    }

    public function validateProductForFavorite($product_id)
    {
        $product = $this->productDao->getProductById($product_id);
        if (!$product) {
            return ['valid' => false, 'message' => 'Product not found'];
        }

        // You could add more validation here (e.g., product must be active)

        return ['valid' => true, 'product' => $product];
    }
}
