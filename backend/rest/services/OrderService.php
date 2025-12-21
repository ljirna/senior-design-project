<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../dao/CartDao.php';
require_once __DIR__ . '/../dao/ProductDao.php';

class OrderService extends BaseService
{
    private $cartDao;
    private $productDao;

    public function __construct()
    {
        $dao = new OrderDao();
        parent::__construct($dao);
        $this->cartDao = new CartDao();
        $this->productDao = new ProductDao();
    }

    public function getOrderById($order_id)
    {
        $order = $this->dao->getOrderById($order_id);
        if ($order) {
            $order['items'] = $this->dao->getOrderItems($order_id);
        }
        return $order;
    }

    public function getOrdersByUserId($user_id, $limit = 20, $offset = 0)
    {
        return $this->dao->getOrdersByUserId($user_id, $limit, $offset);
    }

    public function getAllOrders($limit = 20, $offset = 0)
    {
        return $this->dao->getAllOrders($limit, $offset);
    }

    public function getOrderStatistics($user_id = null)
    {
        return $this->dao->getOrderStatistics($user_id);
    }

    public function getOrdersByStatus($status, $limit = 20, $offset = 0)
    {
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Invalid order status");
        }
        return $this->dao->getOrdersByStatus($status, $limit, $offset);
    }

    public function searchOrders($search_term, $limit = 20, $offset = 0)
    {
        return $this->dao->searchOrders($search_term, $limit, $offset);
    }

    public function createOrderFromCart($user_id, $order_data)
    {
        // Validate cart
        $cart = $this->cartDao->getCartByUserId($user_id);
        if (!$cart) {
            throw new Exception("Cart is empty");
        }

        $cart_items = $this->cartDao->getCartItems($cart['cart_id']);
        if (empty($cart_items)) {
            throw new Exception("Cart is empty");
        }

        // Calculate totals
        $totals = $this->cartDao->getCartTotal($cart['cart_id']);

        // Prepare order data
        $order = [
            'user_id' => $user_id,
            'total_amount' => $totals['subtotal'] + $totals['delivery_total'] + $totals['assembly_total'],
            'order_date' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'delivery_type' => $order_data['delivery_type'] ?? 'standard',
            'assembly_option' => $order_data['assembly_option'] ?? 'none',
            'delivery_fee' => $totals['delivery_total'],
            'assembly_fee' => $totals['assembly_total'],
            'shipping_address' => $order_data['shipping_address']
        ];

        // Prepare order items
        $order_items = [];
        foreach ($cart_items as $item) {
            $order_items[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];

            // Update product stock
            $this->productDao->updateStock($item['product_id'], $item['quantity']);
        }

        // Create order
        $order_id = $this->dao->createOrderWithItems($order, $order_items);

        // Clear cart after successful order
        $this->cartDao->clearCart($cart['cart_id']);

        return $order_id;
    }

    public function updateOrderStatus($order_id, $status)
    {
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Invalid order status");
        }

        $order = $this->dao->getById($order_id);
        if (!$order) {
            throw new Exception("Order not found");
        }

        return $this->dao->updateOrderStatus($order_id, $status);
    }

    public function cancelOrder($order_id)
    {
        $order = $this->dao->getById($order_id);
        if (!$order) {
            throw new Exception("Order not found");
        }

        if ($order['status'] == 'completed') {
            throw new Exception("Cannot cancel a completed order");
        }

        // Restore product stock if cancelling
        if (in_array($order['status'], ['pending', 'processing'])) {
            $items = $this->dao->getOrderItems($order_id);
            foreach ($items as $item) {
                // Note: You might need to implement a method to increase stock
                // $this->productDao->restoreStock($item['product_id'], $item['quantity']);
            }
        }

        return $this->dao->updateOrderStatus($order_id, 'cancelled');
    }

    public function validateOrderData($data)
    {
        $errors = [];

        if (empty($data['shipping_address'])) {
            $errors[] = "Shipping address is required";
        }

        if (isset($data['delivery_type']) && !in_array($data['delivery_type'], ['home', 'store_pickup'])) {
            $errors[] = "Invalid delivery type. Must be 'home' or 'store_pickup'";
        }

        if (isset($data['assembly_option']) && !in_array($data['assembly_option'], ['package', 'worker_assembly'])) {
            $errors[] = "Invalid assembly option. Must be 'package' or 'worker_assembly'";
        }

        if (isset($data['status']) && !in_array($data['status'], ['pending', 'cancelled', 'approved'])) {
            $errors[] = "Invalid status. Must be 'pending', 'cancelled', or 'approved'";
        }

        if (empty($errors)) {
            return ['valid' => true];
        } else {
            return ['valid' => false, 'errors' => $errors];
        }
    }

    public function delete($order_id)
    {
        // Check if order can be deleted (only pending orders)
        $order = $this->dao->getById($order_id);
        if (!$order) {
            throw new Exception("Order not found");
        }

        if ($order['status'] != 'pending') {
            throw new Exception("Only pending orders can be deleted");
        }

        return $this->dao->delete($order_id);
    }
}
