<?php
require_once __DIR__ . '/BaseDao.php';

class CartDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct("cart");
    }

    // Get cart by user ID
    public function getCartByUserId($user_id)
    {
        $stmt = $this->connection->prepare("
            SELECT * FROM cart 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Create or get cart for user
    public function getOrCreateCart($user_id)
    {
        $cart = $this->getCartByUserId($user_id);

        if (!$cart) {
            $stmt = $this->connection->prepare("
                INSERT INTO cart (user_id, created_at) 
                VALUES (:user_id, NOW())
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            $cart_id = $this->connection->lastInsertId();
        }

        return $cart;
    }

    // Get cart items with product details
    public function getCartItems($cart_id)
    {
        $stmt = $this->connection->prepare("
            SELECT 
                ci.*,
                p.name,
                p.description,
                p.price,
                p.delivery_fee_override,
                p.assembly_fee_override,
                c.delivery_fee as category_delivery_fee,
                c.assembly_fee as category_assembly_fee,
                (p.price * ci.quantity) as subtotal,
                (COALESCE(p.delivery_fee_override, c.delivery_fee) * ci.quantity) as delivery_fee_total,
                (COALESCE(p.assembly_fee_override, c.assembly_fee) * ci.quantity) as assembly_fee_total
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.product_id
            JOIN categories c ON p.category_id = c.category_id
            WHERE ci.cart_id = :cart_id
            ORDER BY ci.cart_item_id DESC
        ");
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Add item to cart
    public function addItemToCart($cart_id, $product_id, $quantity = 1)
    {
        // Check if item already exists in cart
        $stmt = $this->connection->prepare("
            SELECT * FROM cart_items 
            WHERE cart_id = :cart_id AND product_id = :product_id
        ");
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $existing = $stmt->fetch();

        if ($existing) {
            // Update quantity
            $stmt = $this->connection->prepare("
                UPDATE cart_items 
                SET quantity = quantity + :quantity 
                WHERE cart_item_id = :cart_item_id
            ");
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':cart_item_id', $existing['cart_item_id']);
            return $stmt->execute();
        } else {
            // Add new item
            $stmt = $this->connection->prepare("
                INSERT INTO cart_items (cart_id, product_id, quantity) 
                VALUES (:cart_id, :product_id, :quantity)
            ");
            $stmt->bindParam(':cart_id', $cart_id);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            return $stmt->execute();
        }
    }

    // Update cart item quantity
    public function updateCartItem($cart_item_id, $quantity)
    {
        if ($quantity <= 0) {
            return $this->removeCartItem($cart_item_id);
        }

        $stmt = $this->connection->prepare("
            UPDATE cart_items 
            SET quantity = :quantity 
            WHERE cart_item_id = :cart_item_id
        ");
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':cart_item_id', $cart_item_id);
        return $stmt->execute();
    }

    // Remove item from cart
    public function removeCartItem($cart_item_id)
    {
        $stmt = $this->connection->prepare("
            DELETE FROM cart_items 
            WHERE cart_item_id = :cart_item_id
        ");
        $stmt->bindParam(':cart_item_id', $cart_item_id);
        return $stmt->execute();
    }

    // Clear cart
    public function clearCart($cart_id)
    {
        $stmt = $this->connection->prepare("
            DELETE FROM cart_items 
            WHERE cart_id = :cart_id
        ");
        $stmt->bindParam(':cart_id', $cart_id);
        return $stmt->execute();
    }

    // Get cart total
    public function getCartTotal($cart_id)
    {
        $stmt = $this->connection->prepare("
            SELECT 
                SUM(p.price * ci.quantity) as subtotal,
                SUM(COALESCE(p.delivery_fee_override, c.delivery_fee) * ci.quantity) as delivery_total,
                SUM(COALESCE(p.assembly_fee_override, c.assembly_fee) * ci.quantity) as assembly_total,
                COUNT(ci.cart_item_id) as item_count
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.product_id
            JOIN categories c ON p.category_id = c.category_id
            WHERE ci.cart_id = :cart_id
        ");
        $stmt->bindParam(':cart_id', $cart_id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Check if cart item belongs to user
    public function cartItemBelongsToUser($cart_item_id, $user_id)
    {
        $stmt = $this->connection->prepare("
            SELECT ci.* 
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.cart_id
            WHERE ci.cart_item_id = :cart_item_id 
              AND c.user_id = :user_id
        ");
        $stmt->bindParam(':cart_item_id', $cart_item_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
