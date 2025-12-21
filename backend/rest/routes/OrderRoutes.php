<?php
require_once __DIR__ . '/../services/OrderService.php';

// Get user's orders
Flight::route('GET /users/@user_id/orders', function ($user_id) {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::orderService()->getOrdersByUserId($user_id, $limit, $offset));
});

// Get order by ID
Flight::route('GET /orders/@order_id', function ($order_id) {
    Flight::json(Flight::orderService()->getOrderById($order_id));
});

// Create order from cart
Flight::route('POST /users/@user_id/orders', function ($user_id) {
    try {
        $data = Flight::request()->data->getData();

        $validation = Flight::orderService()->validateOrderData($data);
        if (!$validation['valid']) {
            Flight::json(['error' => $validation['errors']], 400);
            return;
        }

        $order_id = Flight::orderService()->createOrderFromCart($user_id, $data);
        Flight::json(['message' => 'Order created successfully', 'order_id' => $order_id], 201);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Update order status (Admin only)
Flight::route('PUT /orders/@order_id/status', function ($order_id) {
    try {
        $data = Flight::request()->data->getData();
        Flight::orderService()->updateOrderStatus($order_id, $data['status']);
        Flight::json(['message' => 'Order status updated']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Cancel order
Flight::route('PUT /orders/@order_id/cancel', function ($order_id) {
    try {
        Flight::orderService()->cancelOrder($order_id);
        Flight::json(['message' => 'Order cancelled']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Get order statistics
Flight::route('GET /orders/statistics', function () {
    $user_id = Flight::request()->query['user_id'] ?? null;
    Flight::json(Flight::orderService()->getOrderStatistics($user_id));
});

// Get orders by status (Admin only)
Flight::route('GET /orders/status/@status', function ($status) {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::orderService()->getOrdersByStatus($status, $limit, $offset));
});

// Get all orders (Admin only)
Flight::route('GET /orders', function () {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::orderService()->getAllOrders($limit, $offset));
});

// Search orders (Admin only)
Flight::route('GET /orders/search/@search_term', function ($search_term) {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::orderService()->searchOrders($search_term, $limit, $offset));
});

// Delete order (Admin only - pending orders)
Flight::route('DELETE /orders/@order_id', function ($order_id) {
    try {
        Flight::orderService()->delete($order_id);
        Flight::json(['message' => 'Order deleted successfully']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});
