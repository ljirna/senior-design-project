<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/CartDao.php';
require_once __DIR__ . '/../dao/ProductDao.php';

class CartService extends BaseService
{
    private $productDao;

    public function __construct()
    {
        $dao = new CartDao();
        parent::__construct($dao);
        $this->productDao = new ProductDao();
    }

    public function getCartByUserId($user_id)
    {
        return $this->dao->getOrCreateCart($user_id);
    }

    public function getCartWithItems($user_id)
    {
        $cart = $this->dao->getOrCreateCart($user_id);
        $items = $this->dao->getCartItems($cart['cart_id']);
        $totals = $this->dao->getCartTotal($cart['cart_id']);

        return [
            'cart' => $cart,
            'items' => $items,
            'summary' => $totals,
            'total_amount' => ($totals['subtotal'] ?? 0) + ($totals['delivery_total'] ?? 0) + ($totals['assembly_total'] ?? 0)
        ];
    }

    public function addToCart($user_id, $product_id, $quantity = 1)
    {
        // Validate product
        $product = $this->productDao->getProductById($product_id);
        if (!$product) {
            throw new Exception("Product not found");
        }

        // Validate quantity
        if ($quantity < 1) {
            throw new Exception("Quantity must be at least 1");
        }

        // Check stock if available
        if (isset($product['stock_quantity']) && $product['stock_quantity'] < $quantity) {
            throw new Exception("Insufficient stock");
        }

        $cart = $this->dao->getOrCreateCart($user_id);
        return $this->dao->addItemToCart($cart['cart_id'], $product_id, $quantity);
    }

    public function updateCartItem($user_id, $cart_item_id, $quantity)
    {
        // Verify cart item belongs to user
        $cartItem = $this->dao->cartItemBelongsToUser($cart_item_id, $user_id);
        if (!$cartItem) {
            throw new Exception("Cart item not found or does not belong to user");
        }

        return $this->dao->updateCartItem($cart_item_id, $quantity);
    }

    public function removeFromCart($user_id, $cart_item_id)
    {
        // Verify cart item belongs to user
        $cartItem = $this->dao->cartItemBelongsToUser($cart_item_id, $user_id);
        if (!$cartItem) {
            throw new Exception("Cart item not found or does not belong to user");
        }

        return $this->dao->removeCartItem($cart_item_id);
    }

    public function clearCart($user_id)
    {
        $cart = $this->dao->getCartByUserId($user_id);
        if (!$cart) {
            throw new Exception("Cart not found");
        }

        return $this->dao->clearCart($cart['cart_id']);
    }

    public function getCartTotal($user_id)
    {
        $cart = $this->dao->getCartByUserId($user_id);
        if (!$cart) {
            return [
                'subtotal' => 0,
                'delivery_total' => 0,
                'assembly_total' => 0,
                'item_count' => 0,
                'total_amount' => 0
            ];
        }

        $totals = $this->dao->getCartTotal($cart['cart_id']);
        $total_amount = ($totals['subtotal'] ?? 0) + ($totals['delivery_total'] ?? 0) + ($totals['assembly_total'] ?? 0);

        return array_merge($totals, ['total_amount' => $total_amount]);
    }

    public function validateCartForCheckout($user_id)
    {
        $cart = $this->dao->getCartByUserId($user_id);
        if (!$cart) {
            throw new Exception("Cart is empty");
        }

        $items = $this->dao->getCartItems($cart['cart_id']);
        if (empty($items)) {
            throw new Exception("Cart is empty");
        }

        // Validate stock for all items
        foreach ($items as $item) {
            $product = $this->productDao->getProductById($item['product_id']);
            if (isset($product['stock_quantity']) && $product['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for product: " . $product['name']);
            }
        }

        return [
            'cart_id' => $cart['cart_id'],
            'items' => $items,
            'total' => $this->getCartTotal($user_id)
        ];
    }
}
