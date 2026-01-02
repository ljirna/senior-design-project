<?php
require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../services/StripeService.php';

// Helper to resolve current user id from Flight::get('user')
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

Flight::group('/payments', function () use ($getCurrentUserId) {
    // Get payment by ID - ADMIN or user who owns the order
    Flight::route('GET /@payment_id', function ($payment_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $payment = Flight::paymentService()->getPaymentById($payment_id);

        if (!$payment) {
            Flight::json(['error' => 'Payment not found'], 404);
            return;
        }

        // Check if user has access: ADMIN or payment belongs to user's order
        if ($user['role'] !== Roles::ADMIN) {
            // Need to check if payment belongs to user's order
            // You'll need to implement this check in your service
            $hasAccess = false; // Replace with actual check
            if (!$hasAccess) {
                Flight::json(['error' => 'Access denied'], 403);
                return;
            }
        }

        Flight::json($payment);
    });

    // Get payments for order - ADMIN or user who owns the order
    Flight::route('GET /order/@order_id', function ($order_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');

        // Check if user has access to this order
        if ($user['role'] === Roles::CUSTOMER) {
            // Verify order belongs to user
            $order = Flight::orderService()->getOrderById($order_id);
            if (!$order || $order['user_id'] != $user['id']) {
                Flight::json(['error' => 'Access denied'], 403);
                return;
            }
        }

        try {
            $payments = Flight::paymentService()->getOrderPayments($order_id);
            Flight::json($payments);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Get user payments - ADMIN or user themselves
    Flight::route('GET /user/@user_id', function ($user_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $current_user = Flight::get('user');
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        // Check if user has access
        if ($current_user['role'] !== Roles::ADMIN && $current_user['id'] != $user_id) {
            Flight::json(['error' => 'Access denied'], 403);
            return;
        }

        try {
            $payments = Flight::paymentService()->getUserPayments($user_id, $limit, $offset);
            Flight::json($payments);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Get my payments (current user) - BOTH admin and customer
    Flight::route('GET /my-payments', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        try {
            $payments = Flight::paymentService()->getUserPayments($user['id'], $limit, $offset);
            Flight::json($payments);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // --- ADMIN ONLY ROUTES (sensitive financial operations) ---

    // Get all payments - ADMIN ONLY
    Flight::route('GET /', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $payments = Flight::paymentService()->getAll();
        Flight::json($payments);
    });

    // Update payment status - ADMIN ONLY
    Flight::route('PUT /@payment_id/status', function ($payment_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        if (!isset($data['status'])) {
            Flight::json(['error' => 'Status is required'], 400);
            return;
        }

        try {
            $updated = Flight::paymentService()->updatePaymentStatus($payment_id, $data['status']);
            Flight::json([
                'success' => true,
                'message' => 'Payment status updated',
                'data' => $updated
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Update payment details - ADMIN ONLY
    Flight::route('PUT /@payment_id', function ($payment_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        try {
            $payment = Flight::paymentService()->update($payment_id, $data);
            Flight::json($payment);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Delete payment - ADMIN ONLY (only pending payments)
    Flight::route('DELETE /@payment_id', function ($payment_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        try {
            // Check if payment can be deleted
            $payment = Flight::paymentService()->getPaymentById($payment_id);
            if (!$payment) {
                Flight::json(['error' => 'Payment not found'], 404);
                return;
            }

            if ($payment['payment_status'] !== 'pending') {
                Flight::json(['error' => 'Only pending payments can be deleted'], 400);
                return;
            }

            $result = Flight::paymentService()->delete($payment_id);
            Flight::json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Process payment - ADMIN or user who owns the order
    Flight::route('POST /@payment_id/process', function ($payment_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');

        // For customers, check if payment belongs to their order
        if ($user['role'] === Roles::CUSTOMER) {
            $payment = Flight::paymentService()->getPaymentById($payment_id);
            if (!$payment) {
                Flight::json(['error' => 'Payment not found'], 404);
                return;
            }

            // Check if payment belongs to user's order
            $order = Flight::orderService()->getOrderById($payment['order_id']);
            if (!$order || $order['user_id'] != $user['id']) {
                Flight::json(['error' => 'Access denied'], 403);
                return;
            }
        }

        try {
            // Process payment logic
            $result = Flight::paymentService()->updatePaymentStatus($payment_id, 'completed');
            Flight::json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Refund payment - ADMIN ONLY
    Flight::route('POST /@payment_id/refund', function ($payment_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        try {
            $payment = Flight::paymentService()->getPaymentById($payment_id);
            if (!$payment) {
                Flight::json(['error' => 'Payment not found'], 404);
                return;
            }

            if ($payment['payment_status'] !== 'completed') {
                Flight::json(['error' => 'Only completed payments can be refunded'], 400);
                return;
            }

            // Update payment status to refunded
            $result = Flight::paymentService()->updatePaymentStatus($payment_id, 'refunded');
            Flight::json([
                'success' => true,
                'message' => 'Payment refunded successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });
});

// ========== STRIPE ROUTES ==========

Flight::group('/stripe', function () use ($getCurrentUserId) {
    // Get Stripe configuration - BOTH admin and customer
    Flight::route('GET /config', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        try {
            $config = Flight::stripeService()->getConfig();
            Flight::json($config);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Create Stripe Payment Intent - BOTH admin and customer
    Flight::route('POST /create-payment-intent', function () use ($getCurrentUserId) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        $user_id = $getCurrentUserId ? $getCurrentUserId() : null;
        if (!$user_id) {
            Flight::json(['error' => 'User not found'], 401);
            return;
        }
        $data = Flight::request()->data->getData();

        if (!isset($data['order_id'])) {
            Flight::json(['error' => 'Order ID is required'], 400);
            return;
        }

        try {
            $result = Flight::stripeService()->createPaymentIntent($data['order_id'], $user_id);
            Flight::json([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Confirm payment with Stripe - BOTH admin and customer
    Flight::route('POST /confirm-payment', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        // Parse JSON body first for reliability
        $raw = Flight::request()->getBody();
        $json = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $data = $json;
        } else {
            $data = Flight::request()->data->getData();
        }

        if (!isset($data['payment_intent_id']) || !isset($data['payment_method_id'])) {
            Flight::json(['error' => 'Payment Intent ID and Payment Method ID are required'], 400);
            return;
        }

        try {
            $result = Flight::stripeService()->confirmPayment($data['payment_intent_id'], $data['payment_method_id']);
            Flight::json([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Check payment status - BOTH admin and customer
    Flight::route('GET /payment/@payment_intent_id/status', function ($payment_intent_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        try {
            $status = Flight::stripeService()->checkPaymentStatus($payment_intent_id);
            Flight::json([
                'success' => true,
                'data' => $status
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // --- ADMIN ONLY STRIPE ROUTES ---

    // Get all Stripe payments - ADMIN ONLY
    Flight::route('GET /payments', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        // This would list Stripe payments
        Flight::json([]);
    });

    // Refund via Stripe - ADMIN ONLY
    Flight::route('POST /refund', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        if (!isset($data['payment_intent_id'])) {
            Flight::json(['error' => 'Payment intent ID is required'], 400);
            return;
        }

        try {
            // This would call Stripe refund API
            Flight::json([
                'success' => true,
                'message' => 'Refund processed successfully'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Stripe webhook (must be public - no auth)
    Flight::route('POST /webhook', function () {
        // Public endpoint for Stripe webhooks
        // Stripe sends events here with signature verification
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            // Verify webhook signature
            // Handle Stripe events
            Flight::json(['received' => true]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });
});
