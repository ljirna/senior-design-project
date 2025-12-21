<?php
require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../services/StripeService.php';

// Get payment by ID
Flight::route('GET /payments/@payment_id', function ($payment_id) {
    try {
        $payment = Flight::paymentService()->getPaymentById($payment_id);
        if ($payment) {
            Flight::json($payment);
        } else {
            Flight::json(['error' => 'Payment not found'], 404);
        }
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Get payments for order
Flight::route('GET /orders/@order_id/payments', function ($order_id) {
    try {
        $payments = Flight::paymentService()->getOrderPayments($order_id);
        Flight::json($payments);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Update payment status (Admin)
Flight::route('PUT /payments/@payment_id/status', function ($payment_id) {
    try {
        $data = Flight::request()->data->getData();

        if (!isset($data['status'])) {
            Flight::json(['error' => 'Status is required'], 400);
            return;
        }

        $updated = Flight::paymentService()->updatePaymentStatus($payment_id, $data['status']);
        if ($updated) {
            Flight::json(['message' => 'Payment status updated']);
        } else {
            Flight::json(['error' => 'Payment not found'], 404);
        }
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// ========== STRIPE ROUTES ==========

// Get Stripe configuration
Flight::route('GET /stripe/config', function () {
    try {
        $config = Flight::stripeService()->getConfig();
        Flight::json($config);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Create Stripe Payment Intent
Flight::route('POST /stripe/create-payment-intent', function () {
    try {
        $data = Flight::request()->data->getData();

        if (!isset($data['order_id']) || !isset($data['user_id'])) {
            Flight::json(['error' => 'Order ID and User ID are required'], 400);
            return;
        }

        $result = Flight::stripeService()->createPaymentIntent($data['order_id'], $data['user_id']);
        Flight::json($result);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Confirm payment with Stripe
Flight::route('POST /stripe/confirm-payment', function () {
    try {
        $data = Flight::request()->data->getData();

        if (!isset($data['payment_intent_id']) || !isset($data['payment_method_id'])) {
            Flight::json(['error' => 'Payment Intent ID and Payment Method ID are required'], 400);
            return;
        }

        $result = Flight::stripeService()->confirmPayment($data['payment_intent_id'], $data['payment_method_id']);
        Flight::json($result);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Check payment status
Flight::route('GET /stripe/payment/@payment_intent_id/status', function ($payment_intent_id) {
    try {
        $status = Flight::stripeService()->checkPaymentStatus($payment_intent_id);
        Flight::json($status);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});
