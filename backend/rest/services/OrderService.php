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
        // Load server cart
        $cart = $this->cartDao->getCartByUserId($user_id);
        $cart_items = $cart ? $this->cartDao->getCartItems($cart['cart_id']) : [];
        $usingPayloadItems = false;

        // If server cart is empty, allow creating from payload items (local cart)
        if ((!$cart || empty($cart_items)) && !empty($order_data['items']) && is_array($order_data['items'])) {
            $usingPayloadItems = true;
            $cart_items = [];

            foreach ($order_data['items'] as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity'])) {
                    throw new Exception("Each item must include product_id and quantity");
                }

                $qty = (int)$item['quantity'];
                if ($qty < 1) {
                    throw new Exception("Invalid quantity for product: " . $item['product_id']);
                }

                $product = $this->productDao->getProductWithFees($item['product_id']);
                if (!$product) {
                    throw new Exception("Product not found: " . $item['product_id']);
                }

                if (isset($product['stock_quantity']) && $product['stock_quantity'] < $qty) {
                    throw new Exception("Insufficient stock for product: " . $product['name']);
                }

                $deliveryFee = $product['delivery_fee'] ?? ($product['delivery_fee_override'] ?? 0);
                $assemblyFee = $product['assembly_fee'] ?? ($product['assembly_fee_override'] ?? 0);

                $cart_items[] = [
                    'product_id' => $product['product_id'],
                    'quantity' => $qty,
                    'price' => $product['price'],
                    'delivery_fee' => $deliveryFee,
                    'assembly_fee' => $assemblyFee,
                    'name' => $product['name']
                ];
            }
        }

        if (empty($cart_items)) {
            throw new Exception("Cart is empty");
        }

        // Calculate totals
        if ($usingPayloadItems) {
            $subtotal = 0;
            $deliveryTotal = 0;
            $assemblyTotal = 0;

            foreach ($cart_items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
                $deliveryTotal += ($item['delivery_fee'] ?? 0) * $item['quantity'];
                $assemblyTotal += ($item['assembly_fee'] ?? 0) * $item['quantity'];
            }

            $totals = [
                'subtotal' => $subtotal,
                'delivery_total' => $deliveryTotal,
                'assembly_total' => $assemblyTotal
            ];
        } else {
            $totals = $this->cartDao->getCartTotal($cart['cart_id']);
        }

        // Normalize options (accept legacy aliases but store canonical values)
        $deliveryMap = [
            'home' => 'home',
            'store_pickup' => 'store_pickup',
            'pickup' => 'store_pickup', // legacy alias
        ];
        $rawDelivery = $order_data['delivery_type'] ?? 'store_pickup';
        $deliveryType = $deliveryMap[$rawDelivery] ?? 'store_pickup';

        $assemblyMap = [
            'worker_assembly' => 'worker_assembly',
            'package' => 'package',
            'none' => 'package', // legacy alias for no assembly
        ];
        $rawAssembly = $order_data['assembly_option'] ?? 'package';
        $assemblyOption = $assemblyMap[$rawAssembly] ?? 'package';

        // Apply option-based fee adjustments
        if ($deliveryType === 'store_pickup') {
            $totals['delivery_total'] = 0;
        }

        if ($assemblyOption !== 'worker_assembly') {
            $totals['assembly_total'] = 0;
        }

        // Prepare order data
        $order = [
            'user_id' => $user_id,
            'total_amount' => $totals['subtotal'] + $totals['delivery_total'] + $totals['assembly_total'],
            'order_date' => date('Y-m-d H:i:s'),
            'status' => 'pending',
            'delivery_type' => $deliveryType,
            'assembly_option' => $assemblyOption,
            'delivery_fee' => $totals['delivery_total'],
            'assembly_fee' => $totals['assembly_total'],
            'shipping_address' => $order_data['shipping_address']
        ];

        // Prepare order items and update stock
        $order_items = [];
        foreach ($cart_items as $item) {
            $order_items[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];

            $stockUpdated = $this->productDao->updateStock($item['product_id'], $item['quantity']);
            if (!$stockUpdated) {
                throw new Exception("Insufficient stock for product: " . ($item['name'] ?? $item['product_id']));
            }
        }

        // Create order
        $order_id = $this->dao->createOrderWithItems($order, $order_items);

        // Clear cart after successful order when using server cart
        if ($cart && !$usingPayloadItems) {
            $this->cartDao->clearCart($cart['cart_id']);
        }

        return [
            'order_id' => $order_id,
            'totals' => $totals,
            'delivery_type' => $deliveryType,
            'assembly_option' => $assemblyOption
        ];
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

        if (isset($data['items'])) {
            if (!is_array($data['items']) || empty($data['items'])) {
                $errors[] = "Items must be a non-empty array";
            } else {
                foreach ($data['items'] as $idx => $item) {
                    if (!isset($item['product_id'])) {
                        $errors[] = "Item " . ($idx + 1) . " is missing product_id";
                    }
                    if (!isset($item['quantity']) || (int)$item['quantity'] < 1) {
                        $errors[] = "Item " . ($idx + 1) . " has an invalid quantity";
                    }
                }
            }
        }

        if (isset($data['delivery_type'])) {
            $deliveryType = $data['delivery_type'] === 'pickup' ? 'store_pickup' : $data['delivery_type'];
            if (!in_array($deliveryType, ['home', 'store_pickup'])) {
                $errors[] = "Invalid delivery type. Must be 'home' or 'store_pickup'";
            }
        }

        if (isset($data['assembly_option'])) {
            $assemblyOption = $data['assembly_option'] === 'none' ? 'package' : $data['assembly_option'];
            if (!in_array($assemblyOption, ['package', 'worker_assembly'])) {
                $errors[] = "Invalid assembly option. Must be 'package' or 'worker_assembly'";
            }
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
