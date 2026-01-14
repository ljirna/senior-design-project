<?php
require_once __DIR__ . '/../services/OrderService.php';

Flight::group('/orders', function () {
    $getCurrentUserId = function () {
        $u = Flight::get('user');
        if (is_array($u)) {
            return $u['id'] ?? $u['user_id'] ?? null;
        }
        if (is_object($u)) {
            return $u->id ?? $u->user_id ?? null;
        }
        return null;
    };

    Flight::route('GET /', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        if ($user['role'] === Roles::ADMIN) {
            $orders = Flight::orderService()->getAllOrders($limit, $offset);
        } else {
            $orders = Flight::orderService()->getOrdersByUserId($user['id'], $limit, $offset);
        }

        Flight::json($orders);
    });

    Flight::route('GET /@order_id', function ($order_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $order = Flight::orderService()->getOrderById($order_id);

        if (!$order) {
            Flight::json(['error' => 'Order not found'], 404);
            return;
        }

        if ($user['role'] !== Roles::ADMIN && $order['user_id'] != $user['id']) {
            Flight::json(['error' => 'Access denied'], 403);
            return;
        }

        Flight::json($order);
    });

    Flight::route('POST /from-cart', function () use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user_id = $getCurrentUserId();
        if (!$user_id) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }

        $data = Flight::request()->data->getData();
        if (is_object($data)) {
            $data = (array)$data;
        }

        $validation = Flight::orderService()->validateOrderData($data);
        if (!$validation['valid']) {
            Flight::json(['error' => $validation['errors']], 400);
            return;
        }

        try {
            $order = Flight::orderService()->createOrderFromCart($user_id, $data);
            Flight::json([
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $order['order_id'],
                'totals' => $order['totals'] ?? null,
                'delivery_type' => $order['delivery_type'] ?? null,
                'assembly_option' => $order['assembly_option'] ?? null
            ], 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('GET /user/@user_id', function ($user_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $current_user = Flight::get('user');
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        if ($current_user['role'] !== Roles::ADMIN && $current_user['id'] != $user_id) {
            Flight::json(['error' => 'Access denied'], 403);
            return;
        }

        $orders = Flight::orderService()->getOrdersByUserId($user_id, $limit, $offset);
        Flight::json($orders);
    });


    Flight::route('POST /@order_id/cancel', function ($order_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');

        // Check if customer is trying to cancel their own order
        if ($user['role'] === Roles::CUSTOMER) {
            $order = Flight::orderService()->getOrderById($order_id);
            if (!$order) {
                Flight::json(['error' => 'Order not found'], 404);
                return;
            }

            if ($order['user_id'] != $user['id']) {
                Flight::json(['error' => 'You can only cancel your own orders'], 403);
                return;
            }
        }

        try {
            $result = Flight::orderService()->cancelOrder($order_id);
            Flight::json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('POST /validate', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $data = Flight::request()->data->getData();
        $validation = Flight::orderService()->validateOrderData($data);
        Flight::json($validation);
    });

    // --- ADMIN ONLY ROUTES BELOW ---

    Flight::route('POST /', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        $validation = Flight::orderService()->validateOrderData($data);
        if (!$validation['valid']) {
            Flight::json(['error' => $validation['errors']], 400);
            return;
        }

        if (!isset($data['user_id'])) {
            Flight::json(['error' => 'User ID is required'], 400);
            return;
        }

        try {
            $order = Flight::orderService()->add($data);
            Flight::json($order, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('PUT /@order_id/status', function ($order_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        if (!isset($data['status'])) {
            Flight::json(['error' => 'Status is required'], 400);
            return;
        }

        try {
            $result = Flight::orderService()->updateOrderStatus($order_id, $data['status']);
            Flight::json([
                'success' => true,
                'message' => 'Order status updated',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('PUT /@order_id', function ($order_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        $validation = Flight::orderService()->validateOrderData($data);
        if (!$validation['valid']) {
            Flight::json(['error' => $validation['errors']], 400);
            return;
        }

        try {
            $order = Flight::orderService()->update($order_id, $data);
            Flight::json($order);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('GET /status/@status', function ($status) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        try {
            $orders = Flight::orderService()->getOrdersByStatus($status, $limit, $offset);
            Flight::json($orders);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('GET /search', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $search_term = Flight::request()->query['q'] ?? '';
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        $orders = Flight::orderService()->searchOrders($search_term, $limit, $offset);
        Flight::json($orders);
    });

    Flight::route('DELETE /@order_id', function ($order_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        try {
            $result = Flight::orderService()->delete($order_id);
            Flight::json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('PUT /@order_id/approve', function ($order_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        try {
            $result = Flight::orderService()->updateOrderStatus($order_id, 'approved');
            Flight::json([
                'success' => true,
                'message' => 'Order approved successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('PUT /@order_id/reject', function ($order_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();
        $reason = $data['reason'] ?? 'No reason provided';
        $notes = $data['notes'] ?? '';

        try {
            $result = Flight::orderService()->updateOrderStatus($order_id, 'rejected');
            Flight::json([
                'success' => true,
                'message' => 'Order rejected successfully',
                'reason' => $reason,
                'notes' => $notes,
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });
});